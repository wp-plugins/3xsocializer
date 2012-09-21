<html>
<head>
    <title>Facebook | 3xSocializer</title>
</head>
<body>
<h3>Facebook - setting up an application</h3>
<p>To connect 3xSocializer with your Facebook account, you will first need a Facebook Application.</p>

<p>Get a list of your applications from here: <a href="https://developers.facebook.com/apps" target="_blank">My applications | Facebook Developers</a></p>
<p>Select the application you want, then copy and paste the App ID and App Secret from there.</p>

<p><strong>Haven't created an application yet?</strong></p>

<p>It's easy - we have made simple walkthrough. Go to this link and click on "+ Create New App" to create your application: <a href="https://developers.facebook.com/apps" target="_blank">My applications | Facebook Developers</a></p>

<p>You can put whatever you like in the name. Then fill in captcha and press on "Submit". One more step - select "Website with Facebook Login" and set it to <strong><?php echo "http://".$_SERVER['SERVER_NAME'] ?></strong></p>

<p>After above steps, copy and paste the App ID and App Secret from the app page.</p>
</body>
</html>