(()=>{
  const brands=[...document.querySelectorAll('.brand')];
  const authored={
    '(RYP705H17.1NF0':['(','R','Y','P','7','0','5','H','1','7','.','1','N','F','0'],
    '[rYp70$H!7.1Nf0':['[','r','Y','p','7','0','$','H','!','7','.','1','N','f','0'],
    '<Я¥P70$H|+.[N|=0':['<','Я','¥','P','7','0','$','H','|','+','.','[','N','|=','0'],
    'CR¥P70$H!7.1NF0':['C','R','¥','P','7','0','$','H','!','7','.','1','N','F','0']
  };
  const slots=[['C','(','[','<'],['R','r','Я'],['Y','y','¥'],['P','p'],['T','7','+'],['O','0'],['S','5','$'],['H','h'],['I','1','!','|'],['T','7','+'],['.'],['I','1','['],['N','n'],['F','f','|='],['O','0']];
  const pick=list=>list[Math.floor(Math.random()*list.length)];
  const reduced=window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  brands.forEach(brand=>{
    if(brand.dataset.leetspeakReady==='1') return;
    brand.dataset.leetspeakReady='1';
    const original=brand.textContent.trim();
    const seed=authored[original]||slots.map(pick);
    brand.setAttribute('aria-label','CRYPTOSHIT.INFO');brand.textContent='';
    const nodes=slots.map((options,index)=>{const span=document.createElement('span');span.textContent=seed[index]??pick(options);span.setAttribute('aria-hidden','true');brand.appendChild(span);return{span,options};});
    if(reduced)return;
    const candidates=nodes.filter(node=>node.options.length>1);
    const mutate=()=>{const node=pick(candidates),current=node.span.textContent;let next=current;while(next===current)next=pick(node.options);node.span.textContent=next;window.setTimeout(mutate,1200+Math.random()*3800);};
    window.setTimeout(mutate,1800+Math.random()*2500);
  });

  // Fill the single provenance tx-cost row from the mined receipt.
  const costRow=document.querySelector('[data-tx-cost-row]');
  const fallbackTxLink=document.querySelector('a[href^="https://etherscan.io/tx/0x"]');
  const tx=costRow?.dataset.tx||fallbackTxLink?.href.match(/0x[a-fA-F0-9]{64}/)?.[0];
  if(tx){
    fetch('/gas.php?tx='+encodeURIComponent(tx),{headers:{Accept:'application/json'}})
      .then(response=>response.ok?response.json():null)
      .then(data=>{
        if(!data?.ok||!data.fee_eth)return;
        const target=costRow?.querySelector('span:last-child');
        if(!target)return;
        const fee=Number(data.fee_eth),gas=Number(data.gas_used),gwei=Number(data.gas_price_gwei);
        target.classList.remove('muted');
        target.textContent=(Number.isFinite(fee)?fee.toFixed(6):data.fee_eth)+' ETH';
        if(Number.isFinite(gas)&&Number.isFinite(gwei)){
          const detail=document.createElement('span');detail.className='muted';detail.style.marginLeft='8px';detail.textContent='· '+gas.toLocaleString('en-US')+' gas @ '+gwei.toLocaleString('en-US',{maximumFractionDigits:4})+' Gwei';target.appendChild(detail);
        }
      })
      .catch(()=>{});
  }
})();
