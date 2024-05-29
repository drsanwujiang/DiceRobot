from . import BaseModel


class AuthRequest(BaseModel):
    password: str
