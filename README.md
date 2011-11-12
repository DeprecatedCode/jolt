# Jolt Apache Configuration

	# Use name-based virtual hosting.
	NameVirtualHost *:80

	# Jolt Domain Configuration
	<VirtualHost *:80>
		ServerName jolt.dev
		DocumentRoot "/path/to/Jolt"
	
		# Add all of your custom domains here
		ServerAlias test.dev
	
	<Directory /path/to/Jolt>
		AllowOverride All
	</Directory>
	</VirtualHost>
	
# Then, restart Apache and visit http://jolt.dev

You can add domains at any time by editing the Apache configuration and the
`domains.php` file that was created automatically in the Jolt folder.