<?php
/*=========================================================================

  Program:   CDash - Cross-Platform Dashboard System
  Module:    $Id$
  Language:  PHP
  Date:      $Date$
  Version:   $Revision$

  Copyright (c) 2002 Kitware, Inc.  All rights reserved.
  See Copyright.txt or http://www.cmake.org/HTML/Copyright.html for details.

     This software is distributed WITHOUT ANY WARRANTY; without even 
     the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR 
     PURPOSE.  See the above copyright notices for more information.

=========================================================================*/
// Return a tree of coverage directory with the number of line covered
// and not covered
include_once('api.php');

class CoverageAPI extends CDashAPI
{
  
  /** Return the coverage per directory */
  private function CoveragePerDirectory()
    {
    include_once('../cdash/common.php');  
    if(!isset($this->Parameters['project']))  
      {
      echo "Project not set";
      exit();
      }
      
    $projectid = get_project_id($this->Parameters['project']);
    if(!is_numeric($projectid))
      {
      echo "Project not found";
      exit();
      }
    
    // Select the last build that has coverage from the project  
    $query = pdo_query("SELECT buildid FROM coveragesummary,build WHERE build.id=coveragesummary.buildid
                              AND build.projectid='$projectid' ORDER BY buildid DESC LIMIT 1"); 
    echo pdo_error();
    
    if(pdo_num_rows($query) == 0)
      {
      echo "No coverage entries found for this project";
      exit();   
      }
    $query_array = pdo_fetch_array($query);
    $buildid = $query_array['buildid']; 
    
    // Find the coverage files
    $query = pdo_query("SELECT cf.fullpath,c.loctested,c.locuntested FROM coverage as c,coveragefile as cf
                 WHERE c.fileid=cf.id AND c.buildid='".$buildid."' ORDER BY cf.fullpath ASC"); 
    echo pdo_error();
    $coveragearray = array();
    while($query_array = pdo_fetch_array($query))
      {
      $fullpath = $query_array['fullpath'];
      $paths = explode('/',$fullpath);
      $current = array();
      for($i=1;$i<count($paths)-1;$i++)
        {  
        if($i==1)
          {  
          if(!isset($coveragearray[$paths[$i]]))
            {
            $coveragearray[$paths[$i]] = array();
            }
          $current = &$coveragearray[$paths[$i]];
          }
        else
          {
    
          if($i==count($paths)-2)
            { 
            if(isset($current[$paths[$i]]))
              {
              $v = $current[$paths[$i]];
              $current[$paths[$i]] = (integer)$v+$query_array['locuntested'];
              }
            else
              {
              @$current[$paths[$i]] = $query_array['locuntested'];  
              } 
            unset($current);
            }
          else
            {
            $current[$paths[$i]] = array();
            $current = &$current[$paths[$i]];  
            }
          }  
        }
      }
    return $coveragearray;
    } // end function CoveragePerDirectory
  
  /** Run function */
  function Run()
    {
    switch($this->Parameters['task'])
      {
      case 'directory': return $this->CoveragePerDirectory();
      }
    } 
}

?>