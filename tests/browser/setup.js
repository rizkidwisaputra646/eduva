import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { browserEnv } from '../../playwright.config.js';
export default async function setup(){
 fs.mkdirSync(path.dirname(browserEnv.DB_DATABASE),{recursive:true});
 // This test-owned database is isolated from the application's database.
 fs.writeFileSync(browserEnv.DB_DATABASE,'');
 execFileSync('php',['artisan','migrate','--force','--no-interaction'],{env:browserEnv,stdio:'pipe'});
 const ids=execFileSync('php',['tests/browser/fixture.php'],{env:browserEnv,encoding:'utf8'});
 fs.writeFileSync('storage/framework/testing/browser-fixture.json',ids);
}
