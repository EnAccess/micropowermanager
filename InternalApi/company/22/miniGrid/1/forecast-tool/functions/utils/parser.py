def string_to_bool(string):
    """
    Converts a string to boolean
    """
    if string.lower() == 'true':
        return True
    if string.lower() == 'false':
        return False


def time_to_str(time_now):
    """
    converts datetime to string
    """
    return str(time_now).replace(':', '-')
