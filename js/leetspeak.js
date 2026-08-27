(()=>{
  const brands=[...document.querySelectorAll('.brand')];
  if(!brands.length) return;

  const authored={
    '(RYP705H17.1NF0':['(','R','Y','P','7','0','5','H','1','7','.','1','N','F','0'],
    '[rYp70$H!7.1Nf0':['[','r','Y','p','7','0','$','H','!','7','.','1','N','f','0'],
    '<Я¥P70$H|+.[N|=0':['<','Я','¥','P','7','0','$','H','|','+','.','[','N','|=','0'],
    'CR¥P70$H!7.1NF0':['C','R','¥','P','7','0','$','H','!','7','.','1','N','F','0']
  };

  // Logical positions spell CRYPTOSHIT.INFO. Multi-character glyphs such as |=
  // stay one mutation slot, so the logo can subtly change width while it evolves.
  const slots=[
    ['C','(','[','<'],
    ['R','r','Я'],
    ['Y','y','¥'],
    ['P','p'],
    ['T','7','+'],
    ['O','0'],
    ['S','5','$'],
    ['H','h'],
    ['I','1','!','|'],
    ['T','7','+'],
    ['.'],
    ['I','1','['],
    ['N','n'],
    ['F','f','|='],
    ['O','0']
  ];

  const pick=list=>list[Math.floor(Math.random()*list.length)];
  const reduced=window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  brands.forEach(brand=>{
    if(brand.dataset.leetspeakReady==='1') return;
    brand.dataset.leetspeakReady='1';

    const original=brand.textContent.trim();
    const seed=authored[original] || slots.map(pick);

    brand.setAttribute('aria-label','CRYPTOSHIT.INFO');
    brand.textContent='';

    const nodes=slots.map((options,index)=>{
      const span=document.createElement('span');
      span.textContent=seed[index] ?? pick(options);
      span.setAttribute('aria-hidden','true');
      brand.appendChild(span);
      return {span,options};
    });

    if(reduced) return;

    const candidates=nodes.filter(node=>node.options.length>1);
    const mutate=()=>{
      const node=pick(candidates);
      const current=node.span.textContent;
      let next=current;
      while(next===current) next=pick(node.options);
      node.span.textContent=next;
      window.setTimeout(mutate,1200+Math.random()*3800);
    };

    // Let each page show its authored logo first, then let individual glyphs drift.
    window.setTimeout(mutate,1800+Math.random()*2500);
  });
})();
