import Client from "@/repositories/Client/AxiosClient"

const resource = `/api/tickets/comments`

export default {
  create(commentPm) {
    return Client.post(`${resource}`, commentPm)
  },
}
