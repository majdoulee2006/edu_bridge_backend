<?php
$sqlFilePath = __DIR__ . '/edu_bridge_backend.sql';
$content = file_get_contents($sqlFilePath);

// Find the constraints section
$constraintsStartToken = "-- Constraints for dumped tables";
$constraintsPos = strpos($content, $constraintsStartToken);

if ($constraintsPos === false) {
    die("Error: Could not find constraints section.\n");
}

$headerAndTables = substr($content, 0, $constraintsPos);
$constraintsSection = substr($content, $constraintsPos);

// 1. Extract Dependencies from Constraints Section
$dependencies = []; // table => [dependencies...]
$tables = [];

preg_match_all('/ALTER TABLE `([^`]+)`\s*(.+?);/s', $constraintsSection, $alterMatches, PREG_SET_ORDER);
foreach ($alterMatches as $match) {
    $table = $match[1];
    $tables[$table] = true;
    if (!isset($dependencies[$table])) {
        $dependencies[$table] = [];
    }
    
    $alterBody = $match[2];
    preg_match_all('/REFERENCES `([^`]+)`/i', $alterBody, $refMatches);
    foreach ($refMatches[1] as $refTable) {
        $tables[$refTable] = true;
        if (!in_array($refTable, $dependencies[$table])) {
            $dependencies[$table][] = $refTable;
        }
    }
}

// 2. Parse Tables from Header and Tables Section
$tableStartToken = "-- Table structure for table `";
$viewStartToken = "-- Stand-in structure for view `";
$actualViewStartToken = "-- Structure for view `";

// We'll split the header and tables by "-- --------------------------------------------------------"
$blocks = explode("-- --------------------------------------------------------", $headerAndTables);
$header = array_shift($blocks);

$tableBlocks = [];
foreach ($blocks as $block) {
    if (preg_match('/-- (?:Table|Stand-in) structure for (?:table|view) `([^`]+)`/', $block, $m)) {
        $tableName = $m[1];
        $tables[$tableName] = true;
        if (!isset($tableBlocks[$tableName])) {
            $tableBlocks[$tableName] = "";
        }
        $tableBlocks[$tableName] .= "-- --------------------------------------------------------\n" . ltrim($block, "\r\n");
    } elseif (preg_match('/-- Structure for view `([^`]+)`/', $block, $m)) {
        // Actual view definitions should probably go at the very end before constraints
        $tableName = $m[1];
        $tables[$tableName] = true;
        if (!isset($tableBlocks[$tableName])) {
            $tableBlocks[$tableName] = "";
        }
        $tableBlocks[$tableName] .= "-- --------------------------------------------------------\n" . ltrim($block, "\r\n");
    } else {
        // Unidentified block, just attach to header for safety
        $header .= "-- --------------------------------------------------------\n" . $block;
    }
}

// Initialize empty dependencies for tables that don't have constraints
foreach (array_keys($tables) as $table) {
    if (!isset($dependencies[$table])) {
        $dependencies[$table] = [];
    }
}

// 3. Topological Sort
function topologicalSort($dependencies) {
    $sorted = [];
    $visited = [];
    $temp = []; // For detecting cycles

    $visit = function($node) use (&$visit, &$sorted, &$visited, &$temp, $dependencies) {
        if (isset($temp[$node])) {
            echo "Cycle detected for $node\n";
            return;
        }
        if (!isset($visited[$node])) {
            $temp[$node] = true;
            if (isset($dependencies[$node])) {
                foreach ($dependencies[$node] as $dep) {
                    $visit($dep);
                }
            }
            unset($temp[$node]);
            $visited[$node] = true;
            $sorted[] = $node;
        }
    };

    foreach (array_keys($dependencies) as $node) {
        $visit($node);
    }
    return $sorted;
}

$sortedTables = topologicalSort($dependencies);

// Also add any tables that were found but somehow missed in the sort
foreach (array_keys($tableBlocks) as $table) {
    if (!in_array($table, $sortedTables)) {
        $sortedTables[] = $table;
    }
}

// 4. Reconstruct SQL
$newSql = $header;
foreach ($sortedTables as $table) {
    if (isset($tableBlocks[$table])) {
        $newSql .= $tableBlocks[$table] . "\n\n";
    }
}

// Append constraints section
$newSql .= $constraintsSection;

file_put_contents($sqlFilePath, $newSql);
echo "SQL file sorted successfully.\n";
