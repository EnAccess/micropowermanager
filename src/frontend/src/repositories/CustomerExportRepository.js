import Client, { baseUrl } from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/api/export/customers`
export default {
  async download(slug) {
    return Client.get(`${resource}?${slug}`, {
      responseType: "blob",
    })
  },
}
