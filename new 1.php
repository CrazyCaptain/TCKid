if(isset($_POST['submit'])){
        $this->load->model('evaluate_model');
        
        //check bad words
        $querybadwords = $this->db->query("SELECT word FROM tbl_dictionary");
        $badwords = array();
        $comment = mysql_real_escape_string($_POST['comment']);
        
        $matches = array();
        foreach ($querybadwords->result() as $row){
            $badwords[] = $row->word;//insert words here
        }
        $matchFound = preg_match_all(
                        "/\b(" . implode($badwords,"|") . ")\b/i", 
                        $comment, 
                        $matches
                        );

        $session_data = $this->session->userdata('logged_in_student');
        $id = $session_data['id'];
        $faculty = $_POST['faculty'];
        $course = $_POST['course'];
        $total_ratings = 0;
        $countcateg = $this->db->query("SELECT id FROM tbl_category");
        $numcateg = $countcateg->num_rows();
        
        if ($matchFound) {
            foreach ($matches as $val) {
                $badword = $val[0];
                $this->evaluate_model->badcomments($id, $faculty, $course, $badword);
                break;
            }
        }
        
        for ($catnum = 1; $catnum <= $numcateg; $catnum++) {
            $query2 = $this->db->query("SELECT id, description FROM tbl_characteristic WHERE category = $catnum");
            $num = 1;
            foreach ($query2->result() as $row){
                $char = $row->id;
                $charname = $num . "-" . $catnum;
                $rating = $_POST[$charname];
                $result = $this->evaluate_model->submit($id, $faculty, $course, $char, $rating);
                $num++;
                
                //if($result){
                    //redirect('mainmenu/accounts', 'refresh');
                //}else{
                    //$data['error'] = 'Entered username already exist!';
                    //$data['color'] = '#9B3131';
                    //$this->load->view('addaccounts_view', $data);
                //}
            }
            
            //compute rating and insert to result table
            $queryresult = $this->db->query("SELECT r.ratings FROM tbl_rating r WHERE r.student = '$id' And r.course = '$course' And r.characteristic IN (SELECT c.id FROM tbl_characteristic c WHERE c.category = $catnum);");
            $count_characteristics = $queryresult->num_rows();
            $total = 0;
            $percent = 0;
            foreach ($queryresult->result() as $row){
                $total = $row->ratings + $total;
            }
            
            $total_average = $total / $count_characteristics;
            
            $result = $this->evaluate_model->insert_result($id, $faculty, $course, $catnum, $total_average);
            
            
            //add all category rating and get average
            $query_percent = $this->db->query("SELECT percentage FROM tbl_category WHERE id = $catnum");
            
            foreach ($query_percent->result() as $row){
                $percent = $row->percentage;
            }
            
            $total_ratings = (($total_average/5) * $percent) + $total_ratings;
        }
        $result = $this->evaluate_model->insert_average($id, $faculty, $course, $total_ratings);
        
        if(strlen($comment) != 0){
                $this->evaluate_model->comment($id, $faculty, $course, $comment);
        }
        
        redirect('studentmain', 'refresh');
    }else{
        redirect('studentmain', 'refresh');
    }