import Client from "@/repositories/Client/AxiosClient.js"

const resource = "/api/whatsapp-messaging/contacts"

export default {
  show(personId) {
    return Client.get(`${resource}/${personId}`)
  },
  optIn(personId, data) {
    return Client.post(`${resource}/${personId}/opt-in`, data)
  },
  optOut(personId, data) {
    return Client.post(`${resource}/${personId}/opt-out`, data)
  },
}
