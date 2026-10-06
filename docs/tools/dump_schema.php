$db = 'zz_doc_schema';
$cols = DB::select("SELECT TABLE_NAME t, COLUMN_NAME c, COLUMN_TYPE ty, IS_NULLABLE n, COLUMN_DEFAULT d, COLUMN_KEY k, EXTRA e, COLUMN_COMMENT cm FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='$db' ORDER BY TABLE_NAME, ORDINAL_POSITION");
$fks  = DB::select("SELECT TABLE_NAME t, COLUMN_NAME c, REFERENCED_TABLE_NAME rt, REFERENCED_COLUMN_NAME rc FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA='$db' AND REFERENCED_TABLE_NAME IS NOT NULL ORDER BY TABLE_NAME");
$tables = DB::select("SELECT TABLE_NAME t, TABLE_TYPE tt FROM information_schema.TABLES WHERE TABLE_SCHEMA='$db' ORDER BY TABLE_NAME");
file_put_contents(sys_get_temp_dir().'/schema.json', json_encode(['cols'=>$cols,'fks'=>$fks,'tables'=>$tables], JSON_UNESCAPED_UNICODE));
echo count($tables).' tables, '.count($cols).' cols, '.count($fks).' fks; ' . sys_get_temp_dir();
