import Client from "@/repositories/Client/AxiosClient"

const resource = `/tickets/api/tickets/comments`

export default {
  create(commentPm) {
    return Client.post(`${resource}`, commentPm)
  },
}
