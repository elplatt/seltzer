require 'mysql'
require 'rspec'
require 'watir'
require 'headless'
require 'yaml'

unless @headless
    @headless = Headless.new
    @headless.start
end



test_config = YAML.load_file('features/support/test_config.yml')
browser = Watir::Browser.start(test_config['base_url'])



mysql_param = test_config['mysql']
my = Mysql.connect(mysql_param['host'],
                   mysql_param['username'],
                   mysql_param['password'],
                   mysql_param['database']
                  )





# there must be a better way to do this
# rails has some db.populate() methods or something, but I barely
# know how to spell mysql, so this will do for now

testdata_filename = test_config['mysql']['test_data']
puts "populating the database with the contents of '#{testdata_filename}'"
File.open(testdata_filename) do |fh|
    fh.readlines(';').each do |line|
        my.query(line) unless line.size <= 1
    end
end


# there's no reason to start up a new browser for every scenario, right?
Before do
    @browser = browser
    @test_config = test_config
end


After do
    begin
        step 'I am logged out'
    rescue
        # make sure we're logged out at the end of every scenario
        # we could store the
        @browser.link(:text => 'Log out').click
    end
end


at_exit do
  browser.close
  @headless.destroy
end
