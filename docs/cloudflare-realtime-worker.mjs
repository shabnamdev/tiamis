/**
 * Tiamis optional real-time gateway for Cloudflare Workers + Durable Objects.
 * Bind TIAMIS_ROOM to the Durable Object class and set TIAMIS_PUBLISH_SECRET.
 */
export class TiamisRoom {
  constructor(state) {
    this.state = state;
  }

  async fetch(request) {
    const url = new URL(request.url);
    if (request.headers.get('Upgrade') === 'websocket') {
      const pair = new WebSocketPair();
      const client = pair[0];
      const server = pair[1];
      this.state.acceptWebSocket(server);
      server.send(JSON.stringify({ type: 'connected', room: url.searchParams.get('room') || '' }));
      return new Response(null, { status: 101, webSocket: client });
    }
    if (request.method === 'POST') {
      const payload = await request.json();
      for (const socket of this.state.getWebSockets()) {
        try { socket.send(JSON.stringify(payload)); } catch (error) { /* stale socket */ }
      }
      return Response.json({ ok: true });
    }
    return new Response('Upgrade required', { status: 426 });
  }
}

export default {
  async fetch(request, env) {
    const url = new URL(request.url);
    const room = (url.searchParams.get('room') || 'public').replace(/[^a-zA-Z0-9_-]/g, '').slice(0, 64);
    const id = env.TIAMIS_ROOM.idFromName(room);
    const stub = env.TIAMIS_ROOM.get(id);

    if (request.method === 'POST') {
      const supplied = request.headers.get('X-Tiamis-Realtime-Secret') || '';
      if (!env.TIAMIS_PUBLISH_SECRET || supplied !== env.TIAMIS_PUBLISH_SECRET) {
        return Response.json({ ok: false, error: 'unauthorized' }, { status: 401 });
      }
      const body = await request.json();
      const targetRoom = String(body.room || room).replace(/[^a-zA-Z0-9_-]/g, '').slice(0, 64);
      const target = env.TIAMIS_ROOM.get(env.TIAMIS_ROOM.idFromName(targetRoom));
      return target.fetch(new Request('https://room.internal/', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body.payload || {})
      }));
    }
    return stub.fetch(request);
  }
};
