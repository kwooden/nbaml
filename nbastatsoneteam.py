#import json
import requests
import csv
import sys

headers = {
    'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
    'Accept-Encoding': 'gzip, deflate',
    'Accept-Language': 'en-US,en;q=0.8',
    'Cache-Control':'max-age=0', 
    'Connection': 'keep-alive',
    'Host': 'stats.nba.com',
    'Upgrade-Insecure-Requests': '1',
    'User-Agent': 'Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:63.0) Gecko/20100101 Firefox/63.0'
}
    
def ifnull(var, val):
  if var is None:
    return val
  return var

print 'Number of Arguments:', len(sys.argv), 'arguments.'
print 'Argument List:', str(sys.argv)

homeTeamID = sys.argv[1];
awayTeamID = sys.argv[2];

#with open('nba_stats_game2.csv', 'w') as csvfile:
fieldnames = ['id', 'game_id', 'team_id', 'team_name','FG_PCT', 'FG3_PCT', 'FT_PCT', 'OREB', 'DREB', 'REB', 'AST', 'STL', 'BLK', 'TOV', 'PF', 'OFF_RATING','DEF_RATING','NET_RATING','AST_PCT','AST_TO','AST_RATIO','OREB_PCT','DREB_PCT','REB_PCT','TM_TOV_PCT','EFG_PCT','TS_PCT','PACE','PIE','PTS']
#writer = csv.DictWriter(csvfile, fieldnames=fieldnames)
#writer.writeheader()

#boxscoreList = 'https://stats.nba.com/stats/teamdashboardbygeneralsplits?DateFrom=&DateTo=&GameSegment=&LastNGames=0&LeagueID=00&Location=&MeasureType=Base&Month=0&OpponentTeamID=0&Outcome=&PORound=0&PaceAdjust=N&PerMode=PerGame&Period=0&PlusMinus=N&Rank=N&Season=2018-19&SeasonSegment=&SeasonType=Regular+Season&ShotClockRange=&Split=general&TeamID=1610612737&VsConference=&VsDivision='    
#boxscoreAdv = 'https://stats.nba.com/stats/teamdashboardbygeneralsplits?DateFrom=&DateTo=&GameSegment=&LastNGames=0&LeagueID=00&Location=&MeasureType=Advanced&Month=0&OpponentTeamID=0&Outcome=&PORound=0&PaceAdjust=N&PerMode=PerGame&Period=0&PlusMinus=N&Rank=N&Season=2018-19&SeasonSegment=&SeasonType=Regular+Season&ShotClockRange=&Split=general&TeamID=1610612737&VsConference=&VsDivision='
boxscoreList = 'https://stats.nba.com/stats/teamdashboardbygeneralsplits?DateFrom=&DateTo=&GameSegment=&LastNGames=0&LeagueID=00&Location=&MeasureType=Base&Month=0&OpponentTeamID=0&Outcome=&PORound=0&PaceAdjust=N&PerMode=PerGame&Period=0&PlusMinus=N&Rank=N&Season=2018-19&SeasonSegment=&SeasonType=Regular+Season&ShotClockRange=&Split=general&TeamID='+str(homeTeamID)+'&VsConference=&VsDivision='    
boxscoreAdv = 'https://stats.nba.com/stats/teamdashboardbygeneralsplits?DateFrom=&DateTo=&GameSegment=&LastNGames=0&LeagueID=00&Location=&MeasureType=Advanced&Month=0&OpponentTeamID=0&Outcome=&PORound=0&PaceAdjust=N&PerMode=PerGame&Period=0&PlusMinus=N&Rank=N&Season=2018-19&SeasonSegment=&SeasonType=Regular+Season&ShotClockRange=&Split=general&TeamID='+str(homeTeamID)+'&VsConference=&VsDivision='
ateamboxscoreList = 'https://stats.nba.com/stats/teamdashboardbygeneralsplits?DateFrom=&DateTo=&GameSegment=&LastNGames=0&LeagueID=00&Location=&MeasureType=Base&Month=0&OpponentTeamID=0&Outcome=&PORound=0&PaceAdjust=N&PerMode=PerGame&Period=0&PlusMinus=N&Rank=N&Season=2018-19&SeasonSegment=&SeasonType=Regular+Season&ShotClockRange=&Split=general&TeamID='+str(awayTeamID)+'&VsConference=&VsDivision='    
ateamboxscoreAdv = 'https://stats.nba.com/stats/teamdashboardbygeneralsplits?DateFrom=&DateTo=&GameSegment=&LastNGames=0&LeagueID=00&Location=&MeasureType=Advanced&Month=0&OpponentTeamID=0&Outcome=&PORound=0&PaceAdjust=N&PerMode=PerGame&Period=0&PlusMinus=N&Rank=N&Season=2018-19&SeasonSegment=&SeasonType=Regular+Season&ShotClockRange=&Split=general&TeamID='+str(awayTeamID)+'&VsConference=&VsDivision='

response = requests.get(boxscoreList, headers=headers )
#print(response.json()['resultSets'][0]['rowSet'][0]+"\n")
#print("<br>")
response2 = requests.get(boxscoreAdv,  headers=headers)
#print(response2.json()['resultSets'][0]['rowSet'][0]+"\n") 
#print("<br>")
response3 = requests.get(ateamboxscoreList, headers=headers )
#print(response3.json()['resultSets'][0]['rowSet'][0])
#print("<br>")
response4 = requests.get(ateamboxscoreAdv,  headers=headers)
#print(response4.json()['resultSets'][0]['rowSet'][0]) 
#print("<br>")

# *************** VERIFY OUTPUT IS CORRECT *********************************

teamStats = {}
number = 0
for stats in response.json()['resultSets'][0]['headers']:
    if stats in fieldnames:
        teamStats.update({"hb"+stats : response.json()['resultSets'][0]['rowSet'][0][number]})
    number += 1

number = 0
for stats in response2.json()['resultSets'][0]['headers']:
    if stats in fieldnames:
        teamStats.update({"ha"+stats : response2.json()['resultSets'][0]['rowSet'][0][number]})
    number += 1
    
number = 0
for stats in response3.json()['resultSets'][0]['headers']:
    if stats in fieldnames:
        teamStats.update({"ab"+stats : response3.json()['resultSets'][0]['rowSet'][0][number]})
    number += 1
    
number = 0
for stats in response4.json()['resultSets'][0]['headers']:
    if stats in fieldnames:
        teamStats.update({"aa"+stats : response4.json()['resultSets'][0]['rowSet'][0][number]})
    number += 1
print(teamStats)
    
    #writer.writerow(teamStats)
    
#csvfile.close()
