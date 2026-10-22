import requests,json,concurrent.futures,collections,re
from bs4 import BeautifulSoup
from pathlib import Path
from urllib.parse import urlsplit
base='https://rover.argokov.ru'
rows=json.loads((Path(__file__).resolve().parents[1] / 'data/seo-audit-2026-10.json').read_text())
def check(row):
 url=base+(urlsplit(row[0]).path or '/')
 try:
  r=requests.get(url,timeout=45);s=BeautifulSoup(r.content,'html.parser');errors=[]
  if r.status_code!=200:errors.append('HTTP '+str(r.status_code))
  if not s.title or s.title.get_text()!=row[1]:errors.append('title')
  desc=s.select('meta[name=description]')
  if row[2] is not None and (len(desc)!=1 or desc[0].get('content')!=row[2]):errors.append('description')
  hs=s.select('h1')
  if len(hs)!=1 or hs[0].get_text(strip=True)!=row[3]:errors.append('h1')
  canon=s.select('link[rel=canonical]')
  if len(canon)!=1 or canon[0].get('href')!=r.url:errors.append('canonical')
  graph=[]
  try:
   for tag in s.select('script[type="application/ld+json"]'):
    data=json.loads(tag.string);graph+=data.get('@graph',[data])
  except Exception:errors.append('json-ld')
  branches=[n for n in graph if n.get('@type')=='AutoRepair']
  if len(branches)!=4 or len(set(n['@id'] for n in branches))!=4:errors.append('branches')
  if not any(n.get('@type')=='SiteNavigationElement' for n in graph):errors.append('navigation')
  ids=[x['id'] for x in s.select('[id]')];dupes=[k for k,v in collections.Counter(ids).items() if v>1]
  if dupes:errors.append('duplicate-ids:'+','.join(dupes))
  if s.select('.service-universal-hero') and urlsplit(url).path not in ['/prodat-avtomobil/','/servis/prodat-avtomobil/'] and not s.select('#appointment-content form'):errors.append('content-form')
  return dict(url=url,status=r.status_code,errors=errors,seconds=round(r.elapsed.total_seconds(),2),h1=hs[0].get_text(strip=True) if hs else None,canonical=[x.get('href') for x in canon])
 except Exception as e:return dict(url=url,errors=[str(e)])
with concurrent.futures.ThreadPoolExecutor(max_workers=3) as pool: results=list(pool.map(check,rows))
(Path(__file__).resolve().parent / 'seo-audit-verification.json').write_text(json.dumps(results,ensure_ascii=False,indent=2))
print(json.dumps(dict(checked=len(results),passed=sum(not x['errors'] for x in results),failed=[x for x in results if x['errors']]),ensure_ascii=False,indent=2))
