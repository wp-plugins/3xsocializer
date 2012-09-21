<html>
<head>
    <title>LinkedIn | 3xSocializer</title>
</head>
<body>
<h3>LinkedIn - setting up an application</h3>
<p>To connect 3xSocializer with your LinkedIn accounts, you will first need a LinkedIn Application.</p>

<p>Get a list of your applications from here: <a href="https://www.linkedin.com/secure/developer" target="_blank">List of applications | LinkedIn</a></p>
<p>Select the application you want, then copy and paste the API Key and Secret Key from there.</p>

<p><strong>Haven't created an application yet?</strong></p>

<p>It's easy - we have made simple walkthrough. Go to this link to create your application: <a href="https://www.linkedin.com/secure/developer?newapp=" target="_blank">Add new application | LinkedIn</a></p>

<p>You can put whatever you like in the name, description and Application Use. However, Website URL must be set to <strong><?php echo "http://".$_SERVER['SERVER_NAME'] ?></strong></p>

<p>Live Status must be set to "Live". You can put own email and phone in "Developer Contact Email" and "Phone" </p>

<p>Once you have read and agree to the LinkedIn API Terms of Use, you'll be sent to your new Application.</p>

<p>Copy and paste the API Key and Secret Key.</p>
</body>
</html>