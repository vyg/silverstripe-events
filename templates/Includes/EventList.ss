<ul>
<% loop Events %>
<li class="vevent clearfix">
  <h3 class="summary"><a class="url" href="$Link">$Event.Title</a></h3>
  <p class="dates">$FormattedStartDate</p>
  <% with Event %>$Content.LimitWordCount(60)<% end_with %> <a href="$Link"><% _t('Calendar.MORE','Read more&hellip;') %></a>
</li>
<% end_loop %>
</ul>
