import WhatsappRepository from "../repositories/WhatsappRepository.js"

export class WhatsappService {
  constructor() {
    this.repository = WhatsappRepository
  }

  async getCredentials() {
    return await this.repository.getCredentials()
  }

  async updateCredentials(credentials) {
    return await this.repository.updateCredentials(credentials)
  }

  async getOptIns(params = {}) {
    return await this.repository.getOptIns(params)
  }

  async getOptIn(personId) {
    return await this.repository.getOptIn(personId)
  }

  async updateOptIn(personId, data) {
    return await this.repository.updateOptIn(personId, data)
  }

  async getOptInStats() {
    return await this.repository.getOptInStats()
  }

  async bulkOptIn(data) {
    return await this.repository.bulkOptIn(data)
  }
}
