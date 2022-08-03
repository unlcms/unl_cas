# UNL CAS Drupal 9 module

This module does the following
* allows users to log in via UNL CAS

The unl_cas module should ONLY add functionality to force authentication though UNL. Logic for importing users and user data lives in its own module, unl_user.

The reason for the separation is that twofold:
* testing is impossible if we modify the core login routes (some tests depend on being able to create users and log them in via the cour authentication mechanism)
* The authentication method for UNL might change (cas vs shib), while the user data importing should remain constant (or at least similar).

The redirect behavior executed at '/user/login' can be disabled by setting an environment variable: 'UNLCAS_BYPASS_LOGIN_REDIRECT'.
