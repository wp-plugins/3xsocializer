<html>
<head>
    <title>Twitter | 3xSocializer</title>
</head>
<body>
<h3>Twitter - setting up an application</h3>
<p>To connect 3xSocializer with your Twitter accounts, you will first need a Twitter Application.</p>

<p>Get a list of your applications from here: <a href="https://dev.twitter.com/apps" target="_blank">My applications | Twitter Developers</a></p>
<p>Select the application you want, then copy and paste the Consumer Key and Consumer Secret from there.</p>

<p><strong>Haven't created an application yet?</strong></p>

<p>It's easy - we have made simple walkthrough. Go to this link to create your application: <a href="https://dev.twitter.com/apps/new" target="_blank">Create an application | Twitter Developers</a></p>

<p>You can put whatever you like in the name, description and website. However, Callback URL must be set to <strong><?php echo "http://".$_SERVER['SERVER_NAME'] ?></strong></p>

<p>Once you have accepted the Developer Rules and passed the captcha test, you'll be sent to your new Application.</p>

<p>There is still one more important thing to do, and that is to set Read and Write access for the application. You will find this setting in the "Settings" tab, down in the third section titled "Application Type". Select "Read and Write", then save the settings using the button at the bottom.</p>

<p>After creating the application, copy and paste the Consumer Key and Consumer Secret from the "Details" page (OAuth section).</p>
</body>
</html>