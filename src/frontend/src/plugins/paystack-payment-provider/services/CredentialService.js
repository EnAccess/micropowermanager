import { baseUrl } from "@/repositories/Client/AxiosClient"
import Client from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/api/paystack/credential`

export class CredentialService {
  constructor() {
    this.credential = {
      secretKey: "",
      publicKey: "",
      webhookSecret: "",
      callbackUrl: "",
      merchantName: "",
      environment: "test",
    };
  }

  async getCredential() {
    try {
      const response = await Client.get(`${resource}`);
      if (response.data && response.data.data) {
        this.credential = response.data.data;
      }
    } catch (error) {
      console.error("Error fetching credential:", error);
      throw error;
    }
  }

  async updateCredential() {
    try {
      const response = await Client.put(`${resource}`, this.credential);
      if (response.data && response.data.data) {
        this.credential = response.data.data;
      }
      return response;
    } catch (error) {
      console.error("Error updating credential:", error);
      throw error;
    }
  }
}
