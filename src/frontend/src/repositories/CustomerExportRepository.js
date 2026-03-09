import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/export/customers`
export default {
  async download(slug) {
    return Client.get(`${resource}?${slug}`, {
      responseType: "blob",
    })
  },
}
