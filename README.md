Execute project: 
cd backend && php artisan serve
npm run dev

Test openapi.yaml(ai feature), first up the back(cd backend && php artisan serve) then: 
curl http://localhost:8000/api/test
curl http://localhost:8000/api/movies
curl http://localhost:8000/api/movies/1/showtimes
curl http://localhost:8000/api/showtimes/1/seats

cypress testing:
cd frontend && npm run cy:run