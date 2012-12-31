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


When /^I go to the (.*) tab$/ do |tab_name|
    # this should be made more specific and ensure that it's really finding
    # the tab link on the page. But it's good enough for now
    step "I click the #{tab_name} link"
end


When /^the page contains "(.*)"$/ do |text|
    @browser.text.should include text
end


When /^I enter the following into the form fields:$/ do |table|
    @form_data = table.rows_hash
    @form_data.each do |field_name, value|
        begin
            @browser.text_field(:name => field_name).set(value)
        rescue
            raise "Can't find the field named '#{field_name}' in the form"
        end
    end
end




When /^the page contains a table row like:$/ do |table|
    values = table.raw.first
    found = false

    @browser.tables.each do |page_table|
        page_table.rows.each do |table_row|
            page_values = table_row.cells.collect { |cell| cell.text }
            if values == page_values[0, values.length]
                # so apparently you can't just return from a step definition!?
                # that's why we do this flag business
                found = true
            end
        end
    end

    unless found
        raise "failed to find a table row with the given cell values"
    end
end