import Client from "@/repositories/Client/AxiosClient.js"
import { resources } from "@/resources.js"

export default {
  list() {
    return Client.get(`${resources.ticketOursourcePayoutReports.list}`)
  },
  download(id) {
    return Client.get(
      `${resources.ticketOursourcePayoutReports.download}${id}`,
      {
        responseType: "blob",
      },
    )
  },
}
