
When /^I choose a CSV file to import$/ do
    fld = @browser.file_field(:name => 'member-file')
    fld.should exist
    fld.set('support/import.csv')
end