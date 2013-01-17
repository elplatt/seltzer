Feature: Add, remove, and edit users

  Scenario: Add a new user with the form

    Given I log in as an admin user
    And I go to the Members tab
    And I go to the Add tab

    When I enter the following into the form fields:
      | firstName      | John              |
      | middleName     | K                 |
      | lastName       | Smallberries      |
      | email          | john@yoyodyne.com |
      | phone          | 555-1212          |
      | emergencyName  | John Whorfin      |
      | emergencyPhone | 555-1212          |
      | username       | Jsmallberries     |
      | start          | 1981-1-1          |

    And I click the Add button

    Then the page contains "Add Key Assignment"

    When I go to the Members tab

    Then the page contains a table row like:
      | Smallberries, John K | Big Spender | john@yoyodyne.com | 555-1212 |


  Scenario: Try to add a user with a duplicate username
    Given I log in as an admin user
    And I go to the Members tab
    And I go to the Add tab

    When I enter the following into the form fields:
      | firstName      | Barry          |
      | middleName     | J              |
      | lastName       | Brady          |
      | email          | root@localhost |
      | phone          | 555-1212       |
      | emergencyName  | Alice          |
      | emergencyPhone | 555-1212       |
      | username       | bbrady         |
      | start          | 1981-1-1       |

    And I click the Add button

    Then the page contains "The username 'bbrady' is not available"



  Scenario: Try to add a user without filling in the all the form fields
    Given I log in as an admin user
    And I go to the Members tab
    And I go to the Add tab

    When I enter the following into the form fields:
      | firstName      | Cher           |

    And I click the Add button

    Then the page contains "The Last Name field is required"
    And the page contains "The Email field is required"
    And the page contains "The Phone field is required"



  Scenario: Import new users from CSV file
    Given I log in as an admin user
    And I go to the Members tab
    And I go to the Import tab

    When I choose a CSV file to import
    And I click the Import button

    Then the page contains a table row like:
      | Huxtable, Cliff J | Big Spender | root@localhost | 555-1212 |
