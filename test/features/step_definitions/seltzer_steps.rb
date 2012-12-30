
def get_user(user_description)
    #@test_config['users'].should include user_description, "The test_config.yml file doesn't have '#{user_description}' user"
    user = @test_config['users'][user_description]
    user.should_not be_nil, "The test_config.yml file doesn't have '#{user_description}' user"
    user
end


When /^I click the (.*) link$/ do |link_text|
    link = @browser.link(:text => link_text)
    link.should exist
    link.click
end


When /^I click the (.*) button$/ do |button_text|
    button = @browser.button(:value => button_text)
    button.should exist
    button.click
end

When /^the page contains "(.*)"$/ do |text|
    @browser.text.should include text
end