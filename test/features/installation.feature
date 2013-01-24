Feature: Installation

Scenario: Doing a Fresh install

   Given Seltzer was just freshly installed
   And I create the admin user

   When I log in as an admin user

   Then I am logged in

Scenario: A fresh install has no users 
   Given Seltzer was just feshly installed
   And I create the admin user

   When 
   
