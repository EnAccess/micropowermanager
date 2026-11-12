import WhatsappContactRepository from "../repositories/WhatsappContactRepository.js"

export class WhatsappContactService {
  async getContact(personId) {
    const { data } = await WhatsappContactRepository.show(personId)
    return data.data
  }

  async optIn(personId, consentNote) {
    const { data } = await WhatsappContactRepository.optIn(personId, {
      consent_note: consentNote,
    })
    return data.data
  }

  async optOut(personId, consentNote) {
    const { data } = await WhatsappContactRepository.optOut(personId, {
      consent_note: consentNote || null,
    })
    return data.data
  }
}
