import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/whatsapp-messaging/messages/pricing`

export default {
  get(params) {
    return Client.get(`${resource}`, { params })
  },
}
