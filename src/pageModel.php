<?php

namespace src;


class pageModel {
    private $mysqli;

    public function __construct($filePath){
        $parameters = new Parameters($filePath);
        $dbParameters = $parameters->getParameters();

        $this->mysqli = mysqli_connect($dbParameters[0]['host'], $dbParameters[0]['login'], $dbParameters[0]['password']);
        mysqli_select_db($this->mysqli, $dbParameters[0]['database_name']);

        mysqli_query($this->mysqli, "set character_set_client='utf8'");
        mysqli_query($this->mysqli, "set character_set_connection='utf8'");
        mysqli_query($this->mysqli, "set collation_connection = 'utf8_unicode_ci'");
        mysqli_query($this->mysqli, "set character_set_results='utf8'");
    }

    public function __destruct(){
        mysqli_close($this->mysqli);
    }

    public function getPagesList($pageID=-1){
        //get all list or one page
        if($pageID!==-1){
            $sql = 'SELECT id, title, body FROM pages WHERE id = '.$pageID;
            $result = mysqli_query($this->mysqli, $sql);
            $pages = array();
            if($row = mysqli_fetch_array($result)){
                $pages = $row;
            }
        }else{
            $sql = 'SELECT id, pid, position, title FROM pages ORDER BY pid DESC, position ASC';
            $result = mysqli_query($this->mysqli, $sql);
            $pages = array();
            while($row = mysqli_fetch_array($result)){
                if(!isset($pages[$row['pid']])){
                    $pages[$row['pid']] = '<ul>';
                }

                $pages[$row['pid']] .= '<li id="'.$row['pid'].'_'.$row['id'].'">'.
                    '<a href="/show?pageId='.$row['id'].'&index='.'#PID'.$row['pid'].'#.'.$row['position'].'">'.$row['title'].' #PID'.$row['pid'].'#.'.$row['position'].'</a>'.
                    '<br/><a href="/add?pid='.$row['id'].'" title="Add page">+</a>'.
                    ' <a href="/edit?id='.$row['id'].'" title="Edit page">~</a>'.
                    ' <a href="/delete?id='.$row['id'].'" title="Delete page">-</a>';

                if(isset($pages[$row['id']])){
                    $pages[$row['id']] = str_replace('#PID'.$row['id'].'#', '#PID'.$row['pid'].'#.'.$row['position'], $pages[$row['id']]).'</ul>';
                    $pages[$row['pid']] .= $pages[$row['id']];
                }
                $pages[$row['pid']] .= '</li>';
            }

            $pages = str_replace('#PID0#.', '', $pages[0]).'</ul>';
        }

        mysqli_free_result($result);

        return $pages;
    }

    public function addPage($form_data){
        $position = 0;
        //get new position in selected parent
        $result = mysqli_query($this->mysqli, "SELECT MAX(position) as position FROM pages WHERE pid = ".$form_data['pid']);
        if($result){
            $row = mysqli_fetch_array($result);
            $position = $row['position'];
        }
        //save new page
        $sql = 'INSERT INTO pages(pid, position, title, body, changed)
                VALUES ('.$form_data['pid'].','.($position+1).',"'.$form_data['title'].'","'.$form_data['body'].'","'.date('Y-m-d H:i:s').'")';

        $pageResult = mysqli_query($this->mysqli, $sql);
        mysqli_free_result($result);

        return $pageResult;
    }

    public function savePage($form_data)
    {
        //get new position in selected parent
        $position = 0;
        $result = mysqli_query($this->mysqli, "SELECT MAX(position) as position FROM pages WHERE pid = ".$form_data['pid']);
        if($result){
            $row = mysqli_fetch_array($result);
            $position = $row['position'];
        }
        //update page data
        $sql = 'UPDATE pages
                SET pid="'.$form_data['pid'].'", title="'.$form_data['title'].'", body="'.$form_data['body'].'", position='.($position+1).', changed="'.date('Y-m-d H:i:s').'"
                WHERE id='.$form_data['id'];

        $updateResult = mysqli_query($this->mysqli, $sql);

        //update positions values in old parent
        $position = 1;
        $result = mysqli_query($this->mysqli, "SELECT id FROM pages WHERE pid = ".$form_data['old_pid'].' ORDER BY position ASC');
        if($result){
            while($row = mysqli_fetch_array($result)){
                if(!mysqli_query($this->mysqli, 'UPDATE pages SET position='.($position++).' WHERE id = '.$row['id'])){
                    break;
                }
            }
        }

        mysqli_free_result($result);

        return $updateResult;
    }

    public function deletePage($pageId){
        $message = '';
        //check whether the current page contains a subpages
        $result = mysqli_query($this->mysqli, "SELECT COUNT(id) as page_cnt FROM pages WHERE pid = ".$pageId);
        if($result){
            $row = mysqli_fetch_array($result);
            if($row['page_cnt'] > 0){
                $message = 'Page can\'t by deleted. It contains subpages';
            }else{
                //if not - delete it
                $sql = 'DELETE FROM pages WHERE id = '.$pageId;
                if(mysqli_query($this->mysqli, $sql)){
                    $message = 'Page successfully deleted';
                }else{
                    $message = 'Internal error';
                }
            }
        }

        mysqli_free_result($result);

        return $message;
    }

    public function getParentsList($activeElementID)
    {
        $parentsList = array();
        $result = mysqli_query($this->mysqli, "SELECT id, pid, position, title FROM pages ORDER BY pid DESC, position ASC");
        while($row = mysqli_fetch_array($result)){
            if(!isset($parentsList[$row['pid']])){
                $parentsList[$row['pid']] = '';
            }

            $parentsList[$row['pid']] .= '<option value="'.$row['id'].'" '.($row['id']==$activeElementID ? 'selected="selected"' : '').'>'.
                $row['title'].' #PID'.$row['pid'].'#.'.$row['position'];

            if(isset($parentsList[$row['id']])){
                $parentsList[$row['id']] = str_replace('#PID'.$row['id'].'#', '#PID'.$row['pid'].'#.'.$row['position'], $parentsList[$row['id']]);
                $parentsList[$row['pid']] .= $parentsList[$row['id']];
            }
            $parentsList[$row['pid']] .= '</option>';
        }

        mysqli_free_result($result);
        $parentsList[0] = str_replace('#PID0#.', '', $parentsList[0]);

        return $parentsList[0];
    }

    public function getCurrentPage($pageId)
    {
        $currentPage = array();
        $result = mysqli_query(
            $this->mysqli,"
            SELECT pid, position, title, body
            FROM pages
            WHERE id = ".$pageId);
        if($result){
            while($row = mysqli_fetch_array($result)){
                $currentPage = array(
                    'pid' => $row['pid'],
                    'title' => $row['title'],
                    'body' => $row['body'],
                );
            }
        }

        mysqli_free_result($result);

        return $currentPage;
    }
}