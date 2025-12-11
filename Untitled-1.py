class House():
    def __init__(self, floorSize, noOfDoors, noOfFloors):
        floorSize = floorSize
        noOfFloors = noOfFloors
        noOfDoors = noOfDoors

    def switchOn(self):
        print("Activating..")
        self.lightOpen()
        self.ovenOpen()

    def lightOpen(self):
        print("Light has been activated.")

    def ovenOpen(self):
        print("Oven has been activated.")

class townHouse(House):
    def __init__(self, floorSize, noOfDoors, noOfFloors):
        super().__init__(floorSize, noOfDoors, noOfFloors)
        noOfDoors = 10
        noOfFloors = 5
        print(f"Your number of floors is {noOfFloors}\nYour number of doors is {noOfDoors}")

thouse = townHouse(floorSize=1000, noOfDoors=3, noOfFloors= 2)
print(f"your floor size is: {thouse.floorSize}")

print(townHouse)
thouse.switchOn()

lawjkkldjawkldjkawjdiklaw
