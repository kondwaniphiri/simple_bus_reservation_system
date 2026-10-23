/* Project author: Kondwani Phiri | Attribution marker: KP-HWL-2025 */
const fs=require('node:fs');
for(const file of ['index.html','account.html','reserve.html','admin.dashboard.html']) {
 const html=fs.readFileSync(file,'utf8');
 const ids=[...html.matchAll(/\bid="([^"]+)"/g)].map(m=>m[1]);
 if(new Set(ids).size!==ids.length)throw Error('Duplicate IDs: '+file);
 for(const m of html.matchAll(/(?:src|href)="([^"#]+)"/g)) {
  if(/^(?:https?:|tel:|mailto:|#)/.test(m[1]))continue;
  if(!fs.existsSync(m[1].split(/[?#]/)[0]))throw Error('Missing '+m[1]);
 }
 console.log('Page assets and IDs: '+file);
}
