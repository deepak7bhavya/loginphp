<?php

/********************HELPER FUNCTIONS ******************/

    function clean($string){
        return htmlentities($string);

    }
    function redirect($location){
        return header("Location: {$location}");
    }

    function set_message($message){
        if(!empty($message)){
            $_SESSION['message'] = $message;

        }else{
            $message = "";
        }
    }
    function display_message(){
        if(issset($_SESSION['message'])){
            echo $_SESSION['message'];
            unset($_SESSION['message']); 
        }
    }
    function token_generator(){
        $token = md5(uniqid(mt_rand() ,true));
        return $token;
    }
    function validation_errors($error_message){
        $error_message = <<<DELIMITER
                        <div class="alert alert-warning">
                        <strong>Warning!</strong> $error_message
                        </div>
DELIMITER;
        return $error_message;
    }

    function email_exists($email){
        $sql = "SELECT id FROM users WHERE email = '$email'";
        $result = query($sql);
        if(row_count($result) == 1){
            return true;
        }
        else{
            return false;
        }
    }
    
    function user_exists($user){
        $sql  = "SELECT id FROM users WHERE username = '$user'";
        $result = query($sql);
        if(row_count($result)==1){
            return true;
        }
        else{
            return false;
        }
    }




    /******Validation Functions *******/
    function validate_user_registration(){

        $errors  = [];
        $min = 3;
        $max = 20;

        if($_SERVER['REQUEST_METHOD']=="POST"){
            $first_name         = clean($_POST['first_name']);
            $last_name          = clean($_POST['last_name']);
            $user               = clean($_POST['username']);
            $email              = clean($_POST['email']);
            $password           = clean($_POST['password']);
            $confirm_password   = clean($_POST['confirm_password']);
            

            if(email_exists($email)){
                $errors[] = "Sorry , That Email Already Registered";
            }
            if(user_exists($user)){
                $errors[]= "Sorry , That Username already Taken";
            }

            if(strlen($first_name)< $min){
                $errors[] = "Your First Name Can not be less than {$min} characters";
            }
            if(strlen($last_name)< $min){
                $errors[] = "Your Last Name Can not be less than {$min} characters";
            }
            if(strlen($first_name) > $max){
                $errors[] = "Your First Name Can not be More than {$max} Characters";
            }
            if(strlen($last_name) > $max){
                $errors[] = "Your Last Name Can not be more than {$max} Characters";
            }
            if(strlen($email) < $min){
                $errors[] = "Your Email Can not be more than {$max} Characters";
            }
            if($password !== $confirm_password){
                $errors[] = "Your Password Fields Do not match";
            }
            if(!empty($errors)){
                foreach($errors as $error){
                    echo validation_errors($error);

                }
            }
            else{
                if(register_user($first_name, $last_name, $email, $user, $password)){
                    echo "User Registered";
                }
                else{
                    echo "USer not Register due to Some Other Failure";
                }

            }

        }
    }



    function register_user($first_name, $last_name, $email, $user, $password){
        
        $first_name = escape($first_name);
        $last_name = escape($last_name);

        $email = escape($email);
        $user = escape($user);


        if(email_exists($email)){
            echo "email is ".$email;
            echo "email_exists issue";
            return false;
        }
        else if(user_exists($user)){
            echo "user_exists issue";
            return false;
        }
        else{
            $password = md5($password);
            $validation_code = md5($user . microtime());
            echo "validation code :- ".$validation_code;
            $sql = "INSERT INTO users(first_name,last_name,username,email,password,validation_code,active)";
            $sql.= " VALUES('$first_name','$last_name','$user','$email','$password','$validation_code',0)";
            //echo $sql;
            $result= query($sql);
            confirm($result);
            return true;
        }
    }
    
?>