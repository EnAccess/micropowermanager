import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/tickets/comments`

export default {
  create(commentPm) {
    return Client.post(`${resource}`, commentPm)
  },
}
