
function JoliTree(elt){this.init(elt);}
JoliTree.prototype={init:function(elt){var dds=elt.getElementsByTagName("dd");for(var i=0,l=dds.length;i<l;i++){var dd=dds[i];if(dd.className.indexOf("separator")>=0)
continue;dd.className="jsFolder "+dd.className;var twisty=document.createElement("div");if(dd.className.indexOf("opened")<0){twisty.className="jsTwisty";this.hide(dd,true);}else{dd.className=dd.className.replace(/opened/,"");twisty.className="jsTwisty-opened";}
if(dd.className.indexOf("last")>=0){dd.className=dd.className.replace(/\s*last\s*/,"");dd.className=dd.className.replace(/jsFolder/,"jsFolder-last");twisty.className=twisty.className.replace(/jsTwisty(-opened)*/,"$&-last");}
if(dd.firstChild)
dd.insertBefore(twisty,dd.firstChild);else
dd.appendChild(twisty);var This=this;joliTreeUtils.addEvent(twisty,"click",function(e){This.toggle(e,this);},false);}},toggle:function(e,twisty){if(!twisty.className||twisty.className.indexOf("jsTwisty")<0)
return;if(!e)e=window.event;if(e.stopPropagation)e.stopPropagation();else e.cancelBubble=true;var dd=twisty.parentNode;if(twisty.className.indexOf("jsTwisty-opened")<0){this.hide(dd,false);twisty.className=twisty.className.replace(/jsTwisty/,"jsTwisty-opened");}else{this.hide(dd,true);twisty.className=twisty.className.replace(/jsTwisty-opened/,"jsTwisty");}},hide:function(dd,bool){var dl;var child=dd.firstChild;while(child){if(child.tagName&&child.tagName.toLowerCase()=="dl"){dl=child;break;}
child=child.nextSibling;}
if(!dl)return;if(bool){dl.style.display="none";}else{dl.style.display="";}}}
var joliTreeUtils={addEvent:function(obj,evType,fn,useCapture){if(obj.addEventListener){obj.addEventListener(evType,fn,useCapture);}else{joliTreeUtils.chainHandler(obj,"on"+evType,fn);}},chainHandler:function(obj,handlerName,handler){obj[handlerName]=(function(existingFunction){return function(){handler.apply(this,arguments);if(existingFunction)
existingFunction.apply(this,arguments);};})(handlerName in obj?obj[handlerName]:null);}}