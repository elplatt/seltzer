When /^I enter the username for (.*)$/ do |user_description|
    username = get_user(user_description)['username']
    @browser.text_field(:name => "username").set(username)
end

Then /^the (.*) email is sent to the user$/ do |email_descr|
    answer = ask "Did the user receive the #{email_descr} email?", 60
    unless answer.match(/^[Yy]/)
        raise "The user failed to receive the #{email_descr} email"
    end
end


When /^I open the password reset link from the email$/ do
    url = ask "Enter password reset link from the email", 120
    @browser.goto(url)
end


When /^I make up a new password for (.*)$/ do |user_description|
    user = get_user(user_description)
    new_pw = format("%04d",rand(10000))
    puts "for user '#{user['username']}' old password: #{user['password']} new password: #{new_pw}"
    user['password'] = new_pw
end


When /^I enter and confirm the password for (.*)$/ do |user_description|
    password = get_user(user_description)['password']
    @browser.text_field(:name, "password").set(password)
    @browser.text_field(:name, "confirm").set(password)
end


When /^I log in with username (\w+) and password (\w+)$/ do |username, password|
    @browser.link(:text => 'Log in').click

    @browser.text_field(:name, "username").set(username)
    @browser.text_field(:name, "password").set(password)

    @browser.button(:value, 'Log in').click
end


When /^I am logged in$/ do
    @browser.link(:text => 'Log in').should_not exist
    @browser.link(:text => 'Log out').should exist
end


When /^I am (?:logged out|not logged in)$/ do
    @browser.link(:text => 'Log in').should exist
    @browser.link(:text => 'Log out').should_not exist
end


When /^I log in as (.*)$/ do |user_description|
    user = get_user(user_description)
    step "I log in with username #{user['username']} and password #{user['password']}"
end
