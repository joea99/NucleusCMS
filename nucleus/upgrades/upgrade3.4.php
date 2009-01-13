<?php
function upgrade_do34() {

	if (upgrade_checkinstall(34))
		return 'already installed';
	
	// lengthen tpartname column of nucleus_template
	$query = "	ALTER TABLE `" . sql_table('template') . "`
					MODIFY `tpartname` varchar(64) NOT NULL default '' ;";

	upgrade_query('Altering ' . sql_table('template') . ' table', $query);
	
	// lengthen tdname column of nucleus_template_desc
	$query = "	ALTER TABLE `" . sql_table('template_desc') . "`
					MODIFY `tdname` varchar(64) NOT NULL default '' ;";

	upgrade_query('Altering ' . sql_table('template_desc') . ' table', $query);
	
	// create DebugVars setting
	if (!upgrade_checkIfCVExists('DebugVars')) {
		$query = 'INSERT INTO '.sql_table('config')." VALUES ('DebugVars',0)";
		upgrade_query('Creating DebugVars config value',$query);	
	}
	
	// create DefaultListSize setting
	if (!upgrade_checkIfCVExists('DefaultListSize')) {
		$query = 'INSERT INTO '.sql_table('config')." VALUES ('DefaultListSize',10)";
		upgrade_query('Creating DefaultListSize config value',$query);	
	}

	// 3.3 -> 3.4
	// update database version
	update_version('340');

	
}

?>
