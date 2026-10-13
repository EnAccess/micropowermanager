import Client from "@/repositories/Client/AxiosClient.js"

export const resource = "/api/whatsapp-messaging/contacts"

export default {
  optIn(personId, data) {
    return Client.post(`${resource}/${personId}/opt-in`, data)
  },
  optOut(personId, data) {
    return Client.post(`${resource}/${personId}/opt-out`, data)
  },
}
