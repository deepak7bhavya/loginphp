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
        if(isset($_SESSION['message'])){
            echo $_SESSION['message'];
            unset($_SESSION['message']); 
        }
    }

    function token_generator(){
        $token  = $_SESSION['token'] = md5(uniqid(mt_rand() , true));
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
    
    function send_mail($email,$subject,$msg,$headers){
        return mail($email,$subject,$msg,$headers);
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
                    set_message(
                        "<p class='bg-success text-center'>Please Check your email Inbox and Spam Folder</p>" 
                    );
                    redirect("index.php");
                }
                else{
                    set_message(
                        "<p class='bg-ganger text-center'>Sorry. We could Not Register the user</p>" 
                    );
                    redirect("index.php");
                }

            }

        }
    }


    /******Register User Functions *******/
    function register_user($first_name, $last_name, $email, $user, $password){
        
        $first_name = escape($first_name);
        $last_name = escape($last_name);

        $email = escape($email);
        $user = escape($user);


        if(email_exists($email)){
            //echo "email is ".$email;
            //echo "email_exists issue";
            return false;
        }
        else if(user_exists($user)){
            //echo "user_exists issue";
            return false;
        }
        else{
            $password = md5($password);
            $validation_code = md5($user . microtime());
            //echo "validation code :- ".$validation_code;
            $sql = "INSERT INTO users(first_name,last_name,username,email,password,validation_code,active)";
            $sql.= " VALUES('$first_name','$last_name','$user','$email','$password','$validation_code',0)";
            //echo $sql;
            $result= query($sql);
            confirm($result);


            $subject = "Activation account";
            $msg = "Please Click the link below to activate your Account
            http://localhost/login/activate.php?email=$email&code=$validation_code";
            $headers = "Form: noreply@yourwebsite.com";
            
            send_mail($email,$subject,$msg,$headers);

            return true;
        }
        return false;
    }

    /******Activate user Functions *******/
    function activate_user(){
        if($_SERVER['REQUEST_METHOD']=='GET'){
            if(isset($_GET['email'])){
                $email = clean($_GET['email']);
                $validation_code = clean($_GET['code']);
                $sql = "SELECT id FROM users WHERE email = '".escape($_GET['email'])."' AND validation_code = '".escape($_GET['code'])."'";
                $result = query($sql);
                confirm($result);
                if(row_count($result)==1){
                    $sql2 = "UPDATE users SET active =1 , validation_code=0 WHERE email='".escape($email)."' AND validation_code= '".escape($validation_code)."'";
                    $result2 = query($sql2);
                    confirm($result2);
                    
                    set_message("<p class='bg-success'>Your Account has been Activated please Login </p>");
                    redirect("login.php");
                }
                else{
                    set_message("<p class='bg-success'>Sorry , Your Account could not be Activated </p>");
                    redirect("login.php");
                }
                
                
            
            }
        }
    }


    /******Validate User Login *********/
    function validate_user_login(){
        $email=null;
        $password=null;
        $errors = [];
        $min = 3;
        $max =20;
        

        if($_SERVER['REQUEST_METHOD'] == "POST"){
            echo "it works";
            $email = clean($_POST['email']);
            $password = clean($_POST['password']);
            $remember = isset($_POST['remember']);#FOR CHECKBOXES


            if(empty($email)){
                $errors[] = "EMAIL field cannot be empty";
            }
            if(empty($password)){
                $errors[] = "Password field cannot be empty";
            }

            #echo $email,$password;
            if(!empty($errors)){
                foreach($errors as $error){
                    echo validation_errors($error);
                }
            }
            else{
                #echo "NO ERRORS",$email;
                if(login_user($email,$password,$remember)){
                    redirect("admin.php");
                }
                else{
                    echo validation_errors("Your Credentials are not correct");
                }
            }
        }
        
    }


    /******User Login functions  *********/
    function login_user($email,$password,$remember){
        
        $sql = "SELECT password,id FROM users WHERE email = '".escape($email)."' AND password = '".escape(md5($password))."' AND active=1";
        $result = query($sql);
        if(row_count($result)==1){

            if($remember == "on"){
                setcookie('email',$email,time()+86400);
            }


            $_SESSION['email'] = $email;
            return true;
        }
        else{
            return false;
        }


    }//end of function

    /******User Login functions  *********/

    function logged_in(){
        if(isset($_SESSION['email']) || isset($_COOKIE['email'])){
            return true;
        }
        else{
            return false;
        }
    }

    /******User Login functions  *********/

    function recover_password(){
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            if(isset($_SESSION['token']) && $_POST['token'] === $_SESSION['token']){
                echo "It Works !!"; 
            }
            
        }
    }

    
?>