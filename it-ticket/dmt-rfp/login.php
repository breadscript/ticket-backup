<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta charset="utf-8" />
    <title>TLCI Login</title>
    <meta name="description" content="TLCI module login" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="../assets/font-awesome/4.5.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="../assets/css/fonts.googleapis.com.css" />
    <link rel="stylesheet" href="../assets/css/ace.min.css" />
    <link rel="stylesheet" href="../assets/css/ace-rtl.min.css" />
    <style>
      .alert-info { color:#31708f; background-color:#d9edf7; border-color:#bce8f1; }
    </style>
    <script>
      function getXMLHTTP(){
        var xmlhttp=false; try{ xmlhttp=new XMLHttpRequest(); }catch(e){ try{ xmlhttp=new ActiveXObject('Microsoft.XMLHTTP'); }catch(e1){ try{ xmlhttp=new ActiveXObject('Msxml2.XMLHTTP'); }catch(e2){ xmlhttp=false; } } }
        return xmlhttp;
      }
      function processvalidate(strURL){
        var req=getXMLHTTP();
        if(!req) return;
        req.onreadystatechange=function(){
          if(req.readyState===4){
            if(req.status===200){
              var data=(req.responseText||'').split('|');
              var box=document.getElementById('toggleText');
              var msg=document.getElementById('errorMessage');
              if(data[1]==='4'){
                box.className='alert alert-info alert-dismissable';
                msg.innerHTML='Welcome '+(data[2]||'')+'! Your account is pending approval. Please wait for verification.';
                box.style.display='block';
                clearfields();
              } else if(data[1]==='0' || data[1]==='2' || data[1]==='3'){
                box.className='alert alert-danger alert-dismissable';
                msg.innerHTML='Invalid Username or password. Please try again.';
                box.style.display='block';
                clearfields();
              } else if(data[1]==='1'){
                window.location='index.php';
              } else {
                box.className='alert alert-danger alert-dismissable';
                msg.innerHTML='Unexpected response. Please try again.';
                box.style.display='block';
              }
            } else {
              alert('Login request failed: '+req.statusText);
            }
          }
        };
        req.open('GET', strURL, true);
        req.send(null);
      }
      function clearfields(){ document.form_login.username.value=''; document.form_login.pswd.value=''; }
      function hidediv(){ var ele=document.getElementById('toggleText'); if(ele) ele.style.display='none'; }
      function handleKeyPress(event){ if(event.keyCode===13){ event.preventDefault(); doLogin(); } }
      function doLogin(){
        processvalidate('../session.php?username='+encodeURIComponent(document.getElementById('username').value)+'&pswd='+encodeURIComponent(document.getElementById('pswd').value)+'&remember='+(document.getElementById('remember').value||''));
      }
    </script>
  </head>
  <body class="login-layout">
    <div class="main-container">
      <div class="main-content">
        <div class="row">
          <div class="col-sm-10 col-sm-offset-1">
            <div class="login-container">
              <div class="center">
                <h1>
                  <i class="fa fa-credit-card red" aria-hidden="true"></i>
                  <span class="red">TLCI</span>
                  <span class="white" id="id-text2">Disbursement</span>
                </h1>
              </div>
              <div id="box">
                <div class="panel panel-default">
                  <div class="panel-heading">
                    <h3 class="panel-title"><span class="glyphicon glyphicon-log-in"></span> Log-in</h3>
                  </div>
                  <div class="panel-body">
                    <form role="form" name="form_login" onsubmit="return false;">
                      <fieldset>
                        <div class="form-group">
                          <input class="form-control" placeholder="Username" name="username" id="username" type="text" autofocus onchange="hidediv()" onkeypress="handleKeyPress(event)">
                        </div>
                        <div class="form-group">
                          <input class="form-control" placeholder="Password" id="pswd" name="pswd" type="password" onchange="hidediv()" onkeypress="handleKeyPress(event)">
                        </div>
                        <input name="remember" type="hidden" value="1" id="remember">
                        <div class="alert alert-danger alert-dismissable" style="display:none" id="toggleText">
                          <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                          <span id="errorMessage">Invalid Username or password. Please try again.</span>
                        </div>
                        <button id="loginbtn" type="button" class="btn btn-primary btn-success btn-lg btn-block input-xs" onclick="doLogin()" value="Login">Login</button>
                        <br>
                        <div class="text-center" style="margin-top:15px;">
                          Don't have an account? <a href="../register.php">Register here</a><br>
                          <a href="../forgot-password.php">Forgot Password?</a>
                        </div>
                      </fieldset>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <script src="../assets/js/jquery-2.1.4.min.js"></script>
    <script src="../assets/js/jquery.shake.js"></script>
  </body>
  </html>


