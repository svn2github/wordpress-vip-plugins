# Throttle Media Modal Search Queries

WordPress by default triggers a SQL search query via ajax for every letter put ito the search input box in the media modal.

This results into a multiple SQL queries for every word which is put into the search input box. Those queries might be quite slow on large dataset.

This plugin is throttling the frequency by setting a timeout of 500ms before the AJAX is triggered. In case another key stroke is identified, the timer is cleared and a new one is set. This results, more or less, into a single SQL query per word.

The plugin also listens for "enter" key strokes and issues the AJAX request immediatelly in such case.

The end users should not notice the throttling, but the database servers should be more happy. 
