Feature: Login and Passwords

  Scenario: Log in as an admin user
    Given I am not logged in
    When I log in as an admin user
    Then I am logged in


  Scenario: Log in as a regular user
    Given I am not logged in
    When I log in as a regular user
    Then I am logged in


  Scenario: Log in and out
    Given I am not logged in

    When I log in as a regular user
    And I click the Log out link

    Then I am logged out


  Scenario: Try to log in with a bad username and password
    Given I am not logged in

    When I log in as a bogus user

    Then the page contains "Invalid username/password"
    And I am not logged in

  @manual
  Scenario: Reset a users password
    Given I am not logged in

    When I click the Reset password link
    And I enter the username for a regular user
    And I click the Send Email button

    Then the reset password email is sent to the user

    When I open the password reset link from the email
    And I make up a new password for a regular user
    And I enter and confirm the password for a regular user
    And I click the Change password button

    Then I log in as a regular user
    And I am logged in

  @manual
  Scenario: Reset a users password twice
    Given I am not logged in

    When I click the Reset password link
    And I enter the username for a regular user
    And I click the Send Email button

    Then the reset password email is sent to the user

    When I click the Reset password link
    And I enter the username for a regular user
    And I click the Send Email button

    Then the reset password email is sent to the user

  # the old password should still work
    When I log in as a regular user
    Then I am logged in
