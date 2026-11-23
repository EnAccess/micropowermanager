import Client from "@/repositories/Client/AxiosClient.js"

export const resource = `/api/whatsapp-messaging/templates`

export default {
  notifications() {
    return Client.get(`${resource}/notifications`)
  },
  manual() {
    return Client.get(`${resource}/manual`)
  },
  refresh() {
    return Client.post(`${resource}/refresh`)
  },
}
