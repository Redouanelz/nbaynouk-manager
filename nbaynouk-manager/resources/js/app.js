import './bootstrap';
import './modules/project-services';

const sidebar=document.querySelector('[data-sidebar]'),overlay=document.querySelector('[data-sidebar-overlay]');
const toggleSidebar=open=>{sidebar?.classList.toggle('-translate-x-full',!open);overlay?.classList.toggle('hidden',!open);document.body.classList.toggle('overflow-hidden',open)};
document.querySelector('[data-sidebar-open]')?.addEventListener('click',()=>toggleSidebar(true));overlay?.addEventListener('click',()=>toggleSidebar(false));
document.querySelectorAll('[data-confirm]').forEach(form=>form.addEventListener('submit',event=>{if(!window.confirm(form.dataset.confirm))event.preventDefault()}));
document.querySelectorAll('[data-href]').forEach(row=>row.addEventListener('click',()=>window.location.assign(row.dataset.href)));
const toast=document.querySelector('[data-toast]'),closeToast=()=>toast?.remove();document.querySelector('[data-toast-close]')?.addEventListener('click',closeToast);if(toast)window.setTimeout(closeToast,4500);
const clientSelect=document.querySelector('[data-client-select]'),businessSelect=document.querySelector('[data-business-select]');clientSelect?.addEventListener('change',()=>{businessSelect.querySelectorAll('option[data-client]').forEach(option=>option.hidden=clientSelect.value!==''&&option.dataset.client!==clientSelect.value);if(businessSelect.selectedOptions[0]?.hidden)businessSelect.value=''});
const paymentProject=document.querySelector('[data-payment-project]'),periodSelect=document.querySelector('#billing_period_id'),filterPeriods=()=>periodSelect?.querySelectorAll('option[data-project]').forEach(option=>option.hidden=paymentProject?.value&&option.dataset.project!==paymentProject.value);paymentProject?.addEventListener('change',filterPeriods);filterPeriods();

const search=document.querySelector('[data-global-search]');
if(search){
    const input=search.querySelector('input'),panel=search.querySelector('[data-search-results]'),labels={projects:'Projets',clients:'Clients',businesses:'Entreprises'};let timer;
    const message=(value,error=false)=>{panel.replaceChildren();const paragraph=document.createElement('p');paragraph.className=`p-4 text-sm ${error?'text-danger':'text-muted'}`;paragraph.textContent=value;panel.append(paragraph);panel.classList.remove('hidden')};
    const render=data=>{panel.replaceChildren();Object.entries(data).forEach(([group,items])=>{if(!Array.isArray(items)||!items.length)return;const heading=document.createElement('p');heading.className='search-group';heading.textContent=labels[group]??group;panel.append(heading);items.forEach(item=>{const link=document.createElement('a');link.className='search-result';link.href=item.url;const title=document.createElement('strong'),meta=document.createElement('span');title.textContent=item.label??'';meta.textContent=item.meta??'';link.append(title,meta);panel.append(link)})});if(!panel.children.length)message('Aucun résultat.');else panel.classList.remove('hidden')};
    input.addEventListener('input',()=>{clearTimeout(timer);const query=input.value.trim();if(query.length<2){panel.classList.add('hidden');panel.replaceChildren();return}timer=setTimeout(async()=>{try{const response=await fetch(`${search.dataset.url}?q=${encodeURIComponent(query)}`,{headers:{Accept:'application/json'}});if(!response.ok)throw new Error();render(await response.json())}catch{message('Recherche indisponible.',true)}},280)});
    document.addEventListener('click',event=>{if(!search.contains(event.target))panel.classList.add('hidden')});document.addEventListener('keydown',event=>{if(event.key==='Escape')panel.classList.add('hidden')});
}

document.querySelectorAll('table').forEach(table=>{const labels=[...table.querySelectorAll('thead th')].map(th=>th.textContent.trim());table.querySelectorAll('tbody tr').forEach(row=>[...row.children].forEach((cell,index)=>cell.dataset.label=labels[index]||''))});
