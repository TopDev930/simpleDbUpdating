<?php
ini_set('memory_limit','3200M');

// Replace options
$search_for_array   = $_REQUEST["keyword"];  // the value you want to search for
$replace_with_array = $_REQUEST["ewc_field"];  // the value to replace it with

function endsWith($haystack, $needle)
{
    $length = strlen($needle);
    if ($length == 0) {
        return true;
    }

    return (substr($haystack, -$length) === $needle);
}


function recursive_array_replace($find, $replace, &$data) {
    
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                recursive_array_replace($find, $replace, $data[$key]);
            } else {
                // have to check if it's string to ensure no switching to string for booleans/numbers/nulls - don't need any nasty conversions
                if (is_string($value)) $data[$key] = str_replace($find, $replace, $value);
            }
        }
    } else {
        if (is_string($data)) $data = str_replace($find, $replace, $data);
    }    
} 

function replace_text_db($search_for, $replace_with)
{
// Database Setting  
    
    $host = 'localhost';
    $usr  = 'root';
    $pwd  = '';
    $db   = 'dfygmb';

$mysqli = mysqli_connect($host,$usr,$pwd, $db); 

if (!$mysqli) { echo("Connecting to DB Error: " . mysqli_error($mysqli) . "<br/>"); }

// First, get a list of tables
$SQL = "SHOW TABLES";
$tables_list = $mysqli -> query($SQL);

if (!$tables_list) {
    echo("ERROR: " . mysqli_error($mysqli) . "<br/>$SQL<br/>"); } 


// Loop through the tables
$count_tables_checked = 0; 
$count_items_checked = 0;
$count_items_changed = 0;
while ($table_rows = $tables_list -> fetch_assoc()) {
    
    $count_tables_checked++;
    $table = $table_rows['Tables_in_'.$db];
   
    $SQL = "DESCRIBE ".$table ;    // fetch the table description so we know what to do with it
    $fields_list = $mysqli -> query($SQL);
    
    // Make a simple array of field column names    
    $index_fields = "";  // reset fields for each table.
    $column_name = [];
    $table_index = [];
    $i = 0;
    
    while ($field_rows = $fields_list -> fetch_assoc()) {
                
        $column_name  []= $field_rows['Field'];
		if ($field_rows['Key'] == 'PRI') $table_index []= true ;
		else $table_index []= false;
        
    }

// now let's get the data and do search and replaces on it...
    
    $SQL = "SELECT * FROM ".$table;     // fetch the table contents
    $data = $mysqli -> query($SQL);
    
    if (!$data) {
        echo("ERROR: " . mysqli_error($mysqli) . "<br/>$SQL<br/>"); } 

    while ($row = $data -> fetch_assoc()) {

        // Initialise the UPDATE string we're going to build, and we don't do an update for each damn column...
        
        $need_to_update = false;
        $UPDATE_SQL = 'UPDATE '.$table. ' SET ';
        $WHERE_SQLs = [];
        
        $j = 0;

        foreach ($column_name as $current_column) {
            
            $count_items_checked++;
            $data_to_fix = $row[$current_column];
            $edited_data = $data_to_fix;            // set the same now - if they're different later we know we need to update 
            // $unserialized = unserialize($data_to_fix);  // unserialise - if false returned we don't try to process it as serialised
            
            if (false) {
           
                recursive_array_replace($search_for, $replace_with, $unserialized);                
                $edited_data = serialize($unserialized);
                
              }
            
            else {
                
                    if (is_string($data_to_fix)){
    					$edited_data = preg_replace("/\b" . $search_for . "\b/", $replace_with, $data_to_fix) ;
    				} 
                
				}				
                
            if ($data_to_fix != $edited_data) {   // If they're not the same, we need to add them to the update string
                
                if(strtolower($current_column) != "id" && ! endsWith(strtolower($current_column), "_id"))
                {
                    $count_items_changed++;
                
                    if ($need_to_update != false) 
                    $UPDATE_SQL = $UPDATE_SQL.',';
                    $UPDATE_SQL = $UPDATE_SQL.' '.$current_column.' = "'. $mysqli -> real_escape_string($edited_data).'"' ;
                    $need_to_update = true; // only set if we need to update - avoids wasted UPDATE statements   
                }            
            }
            
            if ($table_index[$j]){
                $WHERE_SQLs []= $current_column.' = "'.$row[$current_column].'" ';
			}
			$j++;
        }
        
        if ($need_to_update) {
            
            $count_updates_run;            
            $WHERE_SQL = join(' AND ', $WHERE_SQLs); // strip off the excess AND - the easiest way to code this without extra flags, etc.            
            $UPDATE_SQL = $UPDATE_SQL . ' WHERE ' . $WHERE_SQL;            
            $result = $mysqli -> query($UPDATE_SQL);
    
            if (!$result) {
                    echo("ERROR: " . mysqli_error($mysqli) . "<br/>$UPDATE_SQL<br/>"); }             
        }        
    }
}

// Report
$report = $count_tables_checked." tables checked; ".$count_items_checked." items checked; ".$count_items_changed." items changed to " . $replace_with;
echo '<p style="margin:auto; text-align:center">';
echo $report;
$mysqli -> close();
}

var_dump($search_for_array);
for($index = 0; $index < count($replace_with_array); $index ++)
{
    if($replace_with_array[$index] != "" && 
        $search_for_array[$index]!= $replace_with_array[$index] && 
        $search_for_array[$index] != ""
    )
    {
        var_dump($replace_with_array[$index]);
        var_dump($search_for_array[$index]);
        // replace_text_db($search_for_array[$index], $replace_with_array[$index]);

    }
}
    

?>