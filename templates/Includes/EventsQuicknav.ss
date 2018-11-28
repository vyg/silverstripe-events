<ul class="event-overview-quick-nav">
  <li><a href="$Link(show/today)"<% if $View == "today" %> class="current"<% end_if %>><% _t('EventOverviewPage.QUICKNAVTODAY', 'Today') %></a></li>
  <li><a href="$Link(show/week)"<% if $View == "week" %> class="current"<% end_if %>><% _t('EventOverviewPage.QUICKNAVTODAY', 'This Week') %></a></li>
  <li><a href="$Link(show/month)"<% if $View == "month" %> class="current"<% end_if %>><% _t('EventOverviewPage.QUICKNAVTODAY', 'This Month') %></a></li>
</ul>
