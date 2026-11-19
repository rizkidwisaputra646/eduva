import { defineConfig } from '@playwright/test';
import path from 'node:path';
const db=path.resolve('storage/framework/testing/browser.sqlite');
export const browserEnv={...process.env,APP_ENV:'testing',APP_URL:'http://127.0.0.1:8011',DB_CONNECTION:'sqlite',DB_DATABASE:db,SESSION_DRIVER:'database',CACHE_STORE:'array',MAIL_MAILER:'log'};
export default defineConfig({testDir:'./tests/browser',timeout:120000,expect:{timeout:20000},workers:1,globalSetup:'./tests/browser/setup.js',use:{baseURL:'http://127.0.0.1:8011',headless:true,trace:'retain-on-failure'},webServer:{command:'php artisan serve --host=127.0.0.1 --port=8011',url:'http://127.0.0.1:8011/up',env:browserEnv,reuseExistingServer:false}});
