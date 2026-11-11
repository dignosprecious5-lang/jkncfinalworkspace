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

Enable both the **Google Calendar API** and **Google Meet REST API** in the
Google Cloud project that owns the OAuth client. Create an OAuth 2.0 **Desktop
application** client and authorize the JK&C calendar-owner Google account with
offline access and these scopes:

- `https://www.googleapis.com/auth/calendar.events`
- `https://www.googleapis.com/auth/meetings.space.settings`

Calendar creates the native conference; the application then resolves the Meet
URL's meeting code with `GET /v2/spaces/{meetingCode}` and PATCHes the canonical
space with `updateMask=config.accessType` and `config.accessType=OPEN`.
Calendar conference data cannot set the Meet admission policy.

Existing refresh tokens do **not** gain a new scope when refreshed. Perform a
one-time reauthorization as the calendar-owner using the local loopback flow
with both scopes and `access_type=offline`, replace only
`GOOGLE_CALENDAR_REFRESH_TOKEN` in the production secret store, then restart
or redeploy. Confirm the grant includes both scopes. Do not print or commit the
refresh token.

The optional `GOOGLE_CALENDAR_OAUTH_BASE` defaults to Google's OAuth endpoint,
`GOOGLE_CALENDAR_TIMEZONE` defaults to `Asia/Manila`, and
`GOOGLE_MEET_API_BASE` defaults to `https://meet.googleapis.com/v2`.

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

The bridge remains the Calendar transport only. Laravel calls Google Meet REST
API itself for the OPEN-access step, so a bridge does not replace the durable
OAuth credentials or Meet settings scope.

## Verification and policy failures

After deployment and reauthorization, create one controlled Online or Hybrid
meeting. Confirm its saved Calendar event ID and Meet URL, then GET
`https://meet.googleapis.com/v2/spaces/{meetingCode}` with the authorized
account and verify `config.accessType` is `OPEN`.

If a 401/403 occurs, do not create a replacement event or Meet. Correct the
OAuth grant or Workspace policy and use the existing retry endpoint; it reuses
the saved event ID and Meet URL. There is no application workaround when a
Workspace administrator blocks OPEN meetings.

Do not put OAuth tokens, client secrets, or bridge secrets in source control,
JSON responses, browser code, or deployment logs.
