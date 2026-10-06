# Google Calendar and Meet production configuration

Meetings use a native Google Calendar conference request for Online and Hybrid
meetings. The Google account that grants the refresh token is the owner of the
created events and Google Meet conferences. It must have write access to the
calendar configured by `GOOGLE_CALENDAR_ID`.

## Required direct OAuth configuration

Use these variables when a Calendar bridge is not deliberately deployed:

- `GOOGLE_CALENDAR_ID` — the Google Calendar ID of the JK&C calendar that will
  contain the events. This is the calendar's **Calendar ID**, not a calendar
  display name or Google account password.
- `GOOGLE_CALENDAR_CLIENT_ID`
- `GOOGLE_CALENDAR_CLIENT_SECRET`
- `GOOGLE_CALENDAR_REFRESH_TOKEN`

Enable the **Google Calendar API** in the Google Cloud project that owns the
OAuth client. Create an OAuth 2.0 **Desktop application** client and authorize
the JK&C calendar-owner Google account with the
`https://www.googleapis.com/auth/calendar.events` scope and offline access.
Store the resulting refresh token only in the production environment. A desktop
client uses the local loopback authorization flow, so this application has no
production web redirect URI to configure for Calendar synchronization.

The optional `GOOGLE_CALENDAR_OAUTH_BASE` defaults to Google's OAuth endpoint,
and `GOOGLE_CALENDAR_TIMEZONE` defaults to `Asia/Manila`.

`GOOGLE_CALENDAR_ACCESS_TOKEN` remains only as a temporary backwards-compatible
fallback. It is not a durable production authentication method and must not be
used for a new production setup.

## Optional bridge configuration

This repository contains a bridge **client only**; it does not contain a
deployable Google Calendar bridge/server. If an independently operated bridge
is later provided, the application will prefer it when both values are set:

- `GOOGLE_CALENDAR_BRIDGE_URL`
- `GOOGLE_CALENDAR_BRIDGE_SECRET`

The bridge must authenticate the supplied secret and support `ping`, `create`,
`update`, and `delete`. Its create/update calls must forward
`conferenceDataVersion=1`, support `hangoutsMeet`, and return `event_id` plus
the native Meet URL when a conference is requested. It must use
`Asia/Manila` unless explicitly configured otherwise.

Do not put OAuth tokens, client secrets, or bridge secrets in source control,
JSON responses, browser code, or deployment logs.
