# Directory

- Dockerfile
- entrypoint.sh
- acf-pro.zip: Paid WP plugin, downloaded from https://www.advancedcustomfields.com/ when customer was logged in. Requires KEY for Pro features.
- acf_data_export.json: Custom fields and pages structure created with ACF plugin. Needs to be imported once if it is a newly created DB.
- docker-compose.yml: Compose with DB. Meant for local development.
- .env_template: Hint for required environment variables. Env files can be passed to docker command.

# Required Resources

- Docker (Docker Desktop if not Linux)
- S3 Bucket
- AWS IAM User with appropiate permissions (S3 access)
- Database instance and database
- ACF Pro Plugin Key

# Environment Variables

AWS_ACCESS_KEY_ID=  
AWS_SECRET_ACCESS_KEY=  
S3_BUCKET=  
AWS_REGION=  

WORDPRESS_DB_HOST=  
WORDPRESS_DB_NAME=  
WORDPRESS_DB_USER=  
WORDPRESS_DB_PASSWORD=  
WORDPRESS_URL=  
WORDPRESS_TITLE=  
WORDPRESS_ADMIN_USER=  
WORDPRESS_ADMIN_PASSWORD=  
WORDPRESS_ADMIN_EMAIL=  
ACF_PRO_LICENSE=  
WP_ENVIRONMENT_TYPE=

# Run DEV

docker compose up  
- Optional 
  - docker compose up -d --build

WP_ENVIRONMENT_TYPE=local is needed for creating application passwords (API Key) on local

## Development Helpers

Endpoints for entities: http://localhost:8000/wp-json/wp/v2/partner?per_page=100  
Size is LIMIT 10 without the param  

If a media file is attached to the post *wp:attachment* key in JSON has all media files data.  

Best option to add data via REST is creating application password (API KEY). *Users > Select the user > Go to bottom > Add key name and save the generated password*  

To add data via REST:

```bash
 curl -X POST http://localhost:8000/wp-json/wp/v2/artist \
  -H "Content-Type: application/json" \
  -u <password_key>:"<application_password>" \
  -d '{
    "title": "Simple Artist Post",
    "status": "publish",
    "acf": {
      "title_box": {
        "en": "Simple Artist Post",
        "fi": "Yksinkertainen Artisti"
      },
      "description_box": {
        "en": "A description in English.",
        "fi": "Kuvaus suomeksi."
      }
    }
  }'

```
# Run PROD

Build:  
```
docker build -t <IMAGE_NAME>:latest .
```

Run:
```  
docker run --env-file .env -p 8000:80 --cap-add SYS_ADMIN --device /dev/fuse cms:latest
```

docker run --env-file .env -p 8000:80 --cap-add SYS_ADMIN --device /dev/fuse \
  -v ./deployment-plugin:/var/www/html/wp-content/plugins/deployment-plugin \
  cms:latest