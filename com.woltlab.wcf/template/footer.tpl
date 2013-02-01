			{if $skipBreadcrumbs|empty}{include file='breadcrumbs' __microdata=false}{/if}
			
		</section>
	</div>
</div>

<footer id="pageFooter" class="layoutFluid footer">
	<div>
		<nav id="footerNavigation" class="navigation navigationFooter clearfix">
			{include file='footerMenu'}
			
			<ul class="navigationIcons">
				<li id="toTopLink" class="toTopLink"><a href="{$__wcf->getAnchor('top')}" title="{lang}wcf.global.scrollUp{/lang}" class="jsTooltip"><span class="icon icon16 icon-arrow-up"></span> <span class="invisible">{lang}wcf.global.scrollUp{/lang}</span></a></li>
				{event name='navigationIcons'}
			</ul>
			
			<ul class="navigationItems">
				{if SHOW_CLOCK}
					<li title="{lang}wcf.date.timezone.{@'/'|str_replace:'.':$__wcf->getUser()->getTimeZone()->getName()|strtolower}{/lang}"><p><span class="icon icon16 icon-time"></span> <span>{@TIME_NOW|plainTime}</span></p></li>
				{/if}
				{event name='navigationItems'}
			</ul>
		</nav>
		
		<div class="footerContent">
			{if ENABLE_BENCHMARK}{include file='benchmark'}{/if}
		
			{event name='copyright'}
		</div>
	</div>
</footer>

<a id="bottom"></a>
