<h2>$Title</h2>

<% include EventsHeader %>
<% include EventsMonthJumper %>
<% include EventsQuicknav %>

<% if Events %>
<div id="event-overview-events">
  <% include EventList %>
</div>
<% else %>
  <p><% _t('NOEVENTS','There are no events') %>.</p>
<% end_if %>
