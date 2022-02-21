app/console doctrine:schema:update --dump-sql
app/console doctrine:cache:clear-metadata 
app/console doctrine:cache:clear-query 
app/console doctrine:cache:clear-result 