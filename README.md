# WordPress Events Plugin

A lightweight WordPress plugin to manage and display events with custom post types, meta fields, and AJAX-powered audience filtering.

## Features
- Custom post type for events
- Meta fields for address, date, time, registration link, more info link, and audience
- Admin columns for event date and status (Active/Expired)
- Sortable event date and status columns
- Frontend display with shortcodes: `[display_events]` and `[display_past_events]`
- AJAX-based audience filtering with client-side caching
- Responsive grid layout
- Event status tags (Today, Tomorrow, Coming Soon)

## Usage
- **Create Events**: Go to Events > Add New in WordPress admin.
- **Set Details**: Fill in title, address, date, time, links, and select audience (Industry, Students, Educators, Community).
- **Display Events**: Use `[display_events limit="X"]` for upcoming events or `[display_past_events]` for past events.
- **Filter by Audience**: Click audience buttons on the frontend to filter events instantly.

## Screenshots
1. **Admin Event List**: Custom columns for date and status.
2. **Event Edit Screen**: Meta boxes for event details and status.
3. **Frontend Display**: Responsive grid with filter buttons.

## Development
- **Files**:
  - `events-plugin.php`: Main plugin file with all functionality.
- **Hooks**:
  - Custom post type: `event`
  - AJAX action: `filter_events`
  - Shortcodes: `display_events`, `display_past_events`
- **Dependencies**:
  - jQuery (WordPress core)
  - jQuery UI Datepicker (for admin)
- **CSS**: Inline styles for frontend and admin.
- **JavaScript**: Inline AJAX and caching logic.

## License
GPL-2.0-or-later

## Tags
wordpress, events, custom-post-type, ajax, filtering, caching, shortcodes, responsive, meta-fields, admin-columns
