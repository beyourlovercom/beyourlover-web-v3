// Display-only regression. No Woo mutation, network, or browser dependency.
import fs from 'node:fs';
import vm from 'node:vm';
import path from 'node:path';
import {fileURLToPath} from 'node:url';
import assert from 'node:assert/strict';
const here=path.dirname(fileURLToPath(import.meta.url));
const theme=path.resolve(here,'../../../theme/beyourlover-v3/assets/js');
const cases=[
  ['Coupon &quot;mk20&quot; cannot be applied because it does not exist.',
   'Coupon "mk20" cannot be applied because it does not exist.'],
  ['Invalid &#34;mk20&#34;', 'Invalid "mk20"'],
  ['Invalid &#x22;mk20&#x22;', 'Invalid "mk20"'],
  ['No &apos;discount&apos; &amp; expired', "No 'discount' & expired"],
  ['&lt;img src=x onerror=alert(1)&gt;', '<img src=x onerror=alert(1)>'],
  ['Plain notice', 'Plain notice'],
  ['&unknown; notice', '&unknown; notice']
];
for(const file of ['checkout.js','cart.js']){
  const src=fs.readFileSync(path.join(theme,file),'utf8');
  const start=src.indexOf('function decodeWooErrorText(value)');
  assert(start>=0,file+': helper missing');
  const end=src.indexOf('\n  }',start);
  assert(end>start,file+': helper end missing');
  const fn=vm.runInNewContext('('+src.slice(start,end+4)+')');
  for(const [given,wanted] of cases){
    assert.equal(fn(given),wanted,file+': incorrect decoded text for '+given);
  }
  assert(src.includes('textContent = decodeWooErrorText('),
    file+': notice must remain plain text, not innerHTML');
  console.log('PASS '+file+': '+cases.length+' decoded display cases, textContent only');
}
console.log('PASS 14/14 decoded error display cases');
