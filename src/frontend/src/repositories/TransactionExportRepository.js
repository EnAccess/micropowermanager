import Client, { baseUrl } from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/api/export/transactions`
export default {
  async download(slug) {
    return Client.get(`${resource}?${slug}`, {
      responseType: "blob",
    })
  },
}
